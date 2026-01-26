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
        Schema::create('tiendas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('envio_gratis')->nullable();
            $table->string('envio_normal')->nullable();
            $table->text('url')->nullable();
            $table->string('url_imagen')->nullable(); // ruta o URL del logo
            $table->unsignedInteger('opiniones')->default(0);
            $table->decimal('puntuacion', 2, 1)->default(0);
            $table->string('url_opiniones')->nullable();
            $table->text('anotaciones_internas')->nullable();
            $table->dateTime('aviso')->nullable();
            $table->string('api')->nullable(); // Campo para especificar qué API usar (scrapingAnt, brightData, etc.)
            $table->enum('mostrar_tienda', ['si', 'no'])->default('si'); // Si la tienda debe mostrarse en el frontend
            $table->enum('como_scrapear', ['automatico', 'manual', 'ambos']);
            $table->enum('scrapear', ['si', 'no'])->default('si'); // Si la tienda debe ser scrapeada
            $table->unsignedInteger('frecuencia_minima_minutos')->nullable()->default(15);
            // Límite mínimo de frecuencia (en minutos) para todas las ofertas de esta tienda.
            // Las ofertas de esta tienda nunca tendrán una frecuencia menor a este valor.
            $table->unsignedInteger('frecuencia_maxima_minutos')->nullable()->default(10080);
            // Límite máximo de frecuencia (en minutos) para todas las ofertas de esta tienda.
            // Las ofertas de esta tienda nunca tendrán una frecuencia mayor a este valor.
            // Valor por defecto: 10080 minutos = 7 días
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiendas');
    }
};
