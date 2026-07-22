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
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->unsignedInteger('clicks')->default(0);
            $table->string('imagen')->nullable();
            $table->json('especificaciones_internas')->nullable();
            $table->text('info_adicional_chatgpt')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->enum('mostrar', ['si', 'no'])->default('si');
            $table->enum('permitir_texto_cantidad_alternativo', ['si', 'no'])->default('no');
            $table->string('unidad_de_medida')->nullable();
            $table->string('configuracion_formulario_producto')->nullable()->default('ninguno');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
