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
        Schema::create('urls_descartadas', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->unsignedBigInteger('tienda_id')->nullable();
            $table->timestamps();

            $table->index('categoria_id');
            $table->index('producto_id');
            $table->index('tienda_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urls_descartadas');
    }
};
