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
            $table->date('fecha');
            $table->decimal('precio_minimo', 8, 3);
            $table->timestamps();

            $table->unique(['producto_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_precios_productos');
    }
};
