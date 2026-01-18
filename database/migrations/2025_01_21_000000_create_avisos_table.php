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
        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->text('texto_aviso');
            $table->dateTime('fecha_aviso');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('avisoable_type'); // Producto, OfertaProducto, Tienda
            $table->unsignedBigInteger('avisoable_id');
            $table->boolean('oculto')->default(false); // Nuevo campo para ocultar avisos
            $table->timestamps();
            
            // Índices para mejorar el rendimiento
            $table->index(['avisoable_type', 'avisoable_id']);
            $table->index('fecha_aviso');
            $table->index('user_id');
            $table->index('oculto'); // Índice para el nuevo campo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avisos');
    }
};
