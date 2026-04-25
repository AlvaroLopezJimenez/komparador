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
        Schema::create('neo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oferta_id')->nullable()->constrained('ofertas_producto')->onDelete('cascade');
            $table->foreignId('producto_id')->nullable()->constrained('productos')->onDelete('cascade');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('cascade');
            $table->foreignId('tienda_id')->nullable()->constrained('tiendas')->onDelete('cascade');
            $table->string('url')->nullable(); // URL
            $table->string('neo')->nullable(); // URL
            $table->enum('aniadida', ['si', 'no'])->default('no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neo');
    }
};
