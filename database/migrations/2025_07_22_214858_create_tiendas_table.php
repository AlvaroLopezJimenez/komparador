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
