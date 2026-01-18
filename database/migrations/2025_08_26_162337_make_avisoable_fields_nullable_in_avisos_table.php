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
        Schema::table('avisos', function (Blueprint $table) {
            $table->string('avisoable_type')->nullable()->change();
            $table->unsignedBigInteger('avisoable_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avisos', function (Blueprint $table) {
            $table->string('avisoable_type')->nullable(false)->change();
            $table->unsignedBigInteger('avisoable_id')->nullable(false)->change();
        });
    }
};
