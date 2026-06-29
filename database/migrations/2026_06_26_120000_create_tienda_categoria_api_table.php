<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tienda_categoria_api', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tienda_id')->constrained('tiendas')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->string('api')->nullable();
            $table->enum('scrapear', ['si', 'no'])->default('si');
            $table->enum('mostrar', ['si', 'no'])->default('si');
            $table->unsignedInteger('frecuencia_minima_minutos')->nullable();
            $table->unsignedInteger('frecuencia_maxima_minutos')->nullable();
            $table->timestamps();

            $table->unique(['tienda_id', 'categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tienda_categoria_api');
    }
};
