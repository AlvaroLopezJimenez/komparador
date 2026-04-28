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
            $table->string('url')->nullable(); // Legacy (texto plano o encv1)
            $table->text('url_cipher')->nullable(); // Nuevo cifrado v2 de url (almacenamiento)
            $table->string('url_lookup', 64)->nullable()->index(); // Nuevo lookup v2 de url (HMAC)
            $table->string('neo')->nullable(); // Legacy (texto plano o encv1)
            $table->text('neo_cipher')->nullable(); // Nuevo cifrado v2 (almacenamiento)
            $table->string('neo_lookup', 64)->nullable()->index(); // Nuevo lookup v2 (HMAC)
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
