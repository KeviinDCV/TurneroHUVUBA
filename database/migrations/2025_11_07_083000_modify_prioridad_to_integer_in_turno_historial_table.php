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
        // Eliminar el campo enum prioridad y agregar campo integer
        Schema::table('turno_historial', function (Blueprint $table) {
            $table->dropColumn('prioridad');
        });

        Schema::table('turno_historial', function (Blueprint $table) {
            // Agregar prioridad como integer (1-5)
            $table->integer('prioridad')->default(3)->after('estado');
            
            // Agregar índice para mejorar búsquedas por prioridad
            $table->index('prioridad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turno_historial', function (Blueprint $table) {
            $table->dropIndex(['prioridad']);
            $table->dropColumn('prioridad');
        });

        Schema::table('turno_historial', function (Blueprint $table) {
            $table->enum('prioridad', ['normal', 'prioritaria'])->default('normal')->after('estado');
        });
    }
};
