<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historico_precios_ofertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oferta_producto_id')->constrained('ofertas_producto')->onDelete('cascade');
            $table->date('fecha');
            $table->decimal('precio_unidad', 8, 4);
            $table->timestamps();

            $table->unique(['oferta_producto_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_precios_ofertas');
    }
};
