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
        Schema::create('correos_aviso_precio', function (Blueprint $table) {
            $table->id();
            $table->string('correo');
            $table->decimal('precio_limite', 8, 2);
            $table->unsignedBigInteger('producto_id');
            $table->string('token_cancelacion', 64)->unique()->nullable();
            $table->dateTime('ultimo_envio_correo')->nullable();
            $table->integer('veces_enviado')->nullable();
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['producto_id', 'precio_limite']);
            $table->index('correo');
            $table->index('token_cancelacion');
            
            // Clave foránea
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correos_aviso_precio');
    }
};
