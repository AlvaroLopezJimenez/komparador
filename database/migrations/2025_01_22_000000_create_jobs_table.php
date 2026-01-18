<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'jobs' que es necesaria para el sistema de colas de Laravel
 * 
 * Esta tabla almacena todos los jobs que están pendientes de ejecución.
 * Laravel usa esta tabla para manejar trabajos en background de forma asíncrona.
 * 
 * IMPORTANTE: Esta tabla es fundamental para que funcionen los Jobs asíncronos.
 * Sin ella, los jobs se ejecutarían de forma síncrona (igual que antes).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla 'jobs' con las columnas necesarias para el sistema de colas
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            // ID único del job
            $table->bigIncrements('id');
            
            // Nombre de la cola (por defecto 'default')
            // Permite tener múltiples colas para diferentes tipos de trabajos
            $table->string('queue')->index();
            
            
            // Datos del job serializados (el objeto Job completo)
            $table->longText('payload');
            
            // Número de intentos realizados
            $table->unsignedTinyInteger('attempts');
            
            // Timestamp del último intento
            $table->unsignedInteger('reserved_at')->nullable();
            
            // Timestamp disponible (cuándo puede ejecutarse)
            $table->unsignedInteger('available_at');
            
            // Timestamp de creación
            $table->unsignedInteger('created_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Elimina la tabla 'jobs' si se hace rollback
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};




















