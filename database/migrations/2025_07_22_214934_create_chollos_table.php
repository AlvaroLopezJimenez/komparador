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
        Schema::create('chollos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->onDelete('cascade');
            $table->foreignId('tienda_id')->constrained('tiendas')->onDelete('cascade');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('cascade');
            $table->string('tipo')->default('producto');
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->string('imagen_grande')->nullable();
            $table->string('imagen_pequena')->nullable();
            $table->decimal('unidades', 10, 2)->nullable();
            $table->decimal('precio_antiguo', 10, 2)->nullable();
            $table->string('precio_nuevo')->nullable();
            $table->decimal('precio_unidad', 10, 4)->nullable();
            $table->string('descuentos')->nullable();
            $table->string('gastos_envio')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('descripcion_interna')->nullable();
            $table->string('url');
            $table->enum('finalizada', ['si', 'no'])->default('no');
            $table->enum('mostrar', ['si', 'no'])->default('si');
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_final')->nullable();
            $table->timestamp('comprobada')->nullable();
            $table->unsignedInteger('frecuencia_comprobacion_min')->default(1440);
            $table->string('meta_titulo')->nullable();
            $table->text('meta_descripcion')->nullable();
            $table->text('anotaciones_internas')->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('me_gusta')->default(0);
            $table->unsignedInteger('no_me_gusta')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chollos');
    }
};

