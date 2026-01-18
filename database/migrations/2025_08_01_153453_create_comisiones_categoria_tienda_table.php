<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comisiones_categoria_tienda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tienda_id')->constrained('tiendas')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->decimal('comision', 5, 2); // Ej: 12.50%
            $table->timestamps();

            $table->unique(['tienda_id', 'categoria_id']); // una comisión por tienda-categoría
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones_categoria_tienda');
    }
};
