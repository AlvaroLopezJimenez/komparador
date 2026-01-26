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
        Schema::create('historico_tiempos_de_actualizacion_precios_ofertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oferta_id')
                ->constrained('ofertas_producto')
                ->onDelete('cascade')
                ->name('hist_tiempos_oferta_id_foreign');
            $table->decimal('precio_total', 10, 2);
            $table->enum('tipo_actualizacion', ['automatico', 'manual']);
            // 'automatico' = actualización por scraping
            // 'manual' = actualización manual desde el panel admin
            $table->unsignedInteger('frecuencia_aplicada_minutos')->nullable();
            // La frecuencia que tenía la oferta ANTES de esta actualización
            $table->unsignedInteger('frecuencia_calculada_minutos')->nullable();
            // La frecuencia calculada DESPUÉS de analizar el historial
            $table->timestamps();
            
            // Índices para optimizar consultas (con nombres cortos para evitar límite de MySQL)
            $table->index(['oferta_id', 'created_at'], 'hist_tiempos_oferta_created_idx');
            $table->index('created_at', 'hist_tiempos_created_idx'); // Para consultas de últimos 30 días
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_tiempos_de_actualizacion_precios_ofertas');
    }
};

