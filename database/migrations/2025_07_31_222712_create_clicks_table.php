<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oferta_id')->constrained('ofertas_producto')->onDelete('cascade');
            $table->string('campaña')->nullable();
            $table->string('ip')->nullable();
            $table->decimal('precio_unidad', 6, 2)->nullable();
            $table->unsignedInteger('posicion')->nullable();
            // Nuevos campos para geolocalización
            $table->string('ciudad')->nullable();
            $table->string('pais')->nullable();
            $table->decimal('latitud', 10, 7)->nullable(); // Precisión para coordenadas
            $table->decimal('longitud', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
