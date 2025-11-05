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
        // Cambiar el tipo de dato de prioridad de enum a tinyInteger
        Schema::table('turno_historial', function (Blueprint $table) {
            $table->tinyInteger('prioridad')->default(3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turno_historial', function (Blueprint $table) {
            $table->enum('prioridad', ['normal', 'prioritaria'])->default('normal')->change();
        });
    }
};
