<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('visitas_usuario', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 80);
            $table->string('session_id', 80);
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->string('origen', 2048)->nullable();
            $table->timestamps();

            $table->unique(['visitor_id', 'session_id', 'producto_id'], 'visitas_usuario_unique');
            $table->index('visitor_id');
            $table->index('producto_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas_usuario');
    }
};
