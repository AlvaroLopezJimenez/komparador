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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action_type'); // 'producto_creado', 'producto_modificado', 'oferta_creada', 'oferta_modificada', 'login'
            $table->foreignId('producto_id')->nullable()->constrained('productos')->onDelete('set null');
            $table->foreignId('oferta_id')->nullable()->constrained('ofertas_producto')->onDelete('set null');
            $table->timestamps();
            
            // Índices para consultas frecuentes
            $table->index(['user_id', 'created_at'], 'user_activities_user_created_idx');
            $table->index(['action_type', 'created_at'], 'user_activities_action_created_idx');
            $table->index('producto_id');
            $table->index('oferta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};

