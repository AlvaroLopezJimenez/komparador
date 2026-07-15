<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('csv_ofertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tienda_id')->constrained('tiendas')->onDelete('cascade');
            $table->text('url');
            $table->string('url_lookup', 64);
            $table->text('nombre')->nullable();
            $table->string('ean', 255)->nullable();
            $table->string('isbn', 255)->nullable();
            $table->string('upc', 255)->nullable();
            $table->string('mpn', 255)->nullable();
            $table->string('gtin', 255)->nullable();
            $table->decimal('precio', 8, 2)->nullable();
            $table->decimal('envio', 4, 2)->nullable();
            $table->unsignedTinyInteger('stock')->nullable();
            $table->enum('aniadida_neo', ['si', 'no'])->default('no');
            $table->timestamps();

            $table->unique(['tienda_id', 'url_lookup']);
            $table->index('tienda_id');
            $table->index('ean');
            $table->index('isbn');
            $table->index('upc');
            $table->index('mpn');
            $table->index('gtin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csv_ofertas');
    }
};
