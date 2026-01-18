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
        Schema::create('producto_oferta_mas_barata_por_producto', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('oferta_id');
            $table->unsignedBigInteger('tienda_id');
            $table->decimal('precio_total', 10, 2);
            $table->decimal('precio_unidad', 10, 4);
            $table->decimal('unidades', 10, 3);
            $table->string('url')->nullable();
            $table->timestamps();
            
            // Índice único para producto_id (solo puede haber una oferta más barata por producto)
            $table->unique('producto_id');
            
            // Índices para búsquedas rápidas
            $table->index('oferta_id');
            $table->index('tienda_id');
            
            // Foreign keys (opcional, pero recomendado para integridad referencial)
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('oferta_id')->references('id')->on('ofertas_producto')->onDelete('cascade');
            $table->foreign('tienda_id')->references('id')->on('tiendas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_oferta_mas_barata_por_producto');
    }
};
