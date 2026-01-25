<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('marca');
            $table->string('modelo');
            $table->string('talla')->nullable();
            $table->decimal('precio', 8, 3)->nullable();
            $table->integer('rebajado')->nullable();
            $table->string('unidadDeMedida')->nullable();
            $table->json('imagen_grande')->nullable();
            $table->json('imagen_pequena')->nullable();
            $table->json('producto_especificaciones_internas')->nullable();
            $table->string('titulo');
            $table->string('subtitulo')->nullable();
            $table->text('descripcion_corta')->nullable();
            $table->text('descripcion_larga')->nullable();
            $table->json('caracteristicas')->nullable();
            $table->json('pros')->nullable();
            $table->json('contras')->nullable();
            $table->json('faq')->nullable();
            $table->json('keys_relacionados')->nullable();
            $table->unsignedInteger('id_categoria_productos_relacionados')->nullable();
            $table->string('slug')->unique();
            $table->json('valoraciones')->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');
            $table->foreignId('categoria_id_especificaciones_internas')->nullable()->constrained('categorias')->onDelete('set null');
            $table->json('categoria_especificaciones_internas_elegidas')->nullable();
            $table->json('especificaciones_busqueda')->nullable();
            $table->text('especificaciones_busqueda_texto')->nullable();
            $table->json('grupos_de_ofertas')->nullable();
            $table->string('meta_titulo')->nullable();
            $table->text('meta_description')->nullable();
            $table->enum('obsoleto', ['si', 'no'])->default('no');
            $table->enum('mostrar', ['si', 'no'])->default('si');
            $table->text('anotaciones_internas')->nullable();
            $table->dateTime('aviso')->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();
        });
        
        // Crear índice fulltext para búsquedas rápidas
        DB::statement('ALTER TABLE productos ADD FULLTEXT INDEX idx_especificaciones_busqueda_texto (especificaciones_busqueda_texto)');
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
