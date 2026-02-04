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
        Schema::create('actividad_sospechosa', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->index();
            $table->string('fingerprint', 64)->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('endpoint', 255);
            $table->string('method', 10)->default('GET');
            $table->integer('score')->default(0);
            $table->string('accion_tomada', 50)->nullable(); // 'normal', 'slowdown', 'captcha', 'temp_ban', 'prolonged_ban'
            $table->text('detalles')->nullable(); // JSON con detalles de heurísticas detectadas
            $table->timestamp('created_at')->index();
            
            // Índices compuestos para consultas frecuentes
            $table->index(['ip', 'created_at'], 'act_sospechosa_ip_created_idx');
            $table->index(['fingerprint', 'created_at'], 'act_sospechosa_fp_created_idx');
            $table->index(['score', 'created_at'], 'act_sospechosa_score_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividad_sospechosa');
    }
};






