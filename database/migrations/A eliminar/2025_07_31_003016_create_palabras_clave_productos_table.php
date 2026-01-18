<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('palabras_clave_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained()->onDelete('cascade');
            $table->string('palabra');
            $table->string('codigo')->unique();
            $table->enum('activa', ['si', 'no'])->default('si');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('palabras_clave_productos');
    }
};
