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
            $table->decimal('precio', 8, 2)->nullable();
            $table->decimal('envio', 4, 2)->nullable();
            $table->unsignedTinyInteger('stock')->nullable();
            $table->timestamps();

            $table->unique(['tienda_id', 'url_lookup']);
            $table->index('tienda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csv_ofertas');
    }
};
