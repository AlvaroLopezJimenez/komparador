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
        Schema::create('ejecuciones_global', function (Blueprint $table) {
            $table->id();
            $table->dateTime('inicio');
            $table->dateTime('fin')->nullable();
            $table->string('nombre');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('total_guardado')->default(0);
            $table->unsignedInteger('total_errores')->default(0);
            $table->json('log')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eejecuciones_global');
    }
};
