<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Eliminar el campo enum prioridad y agregar campo integer
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropColumn('prioridad');
        });

        Schema::table('turnos', function (Blueprint $table) {
            // Agregar prioridad como integer (1-5)
            // 1 = A (más baja), 2 = B, 3 = C (media), 4 = D, 5 = E (más alta)
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
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropIndex(['prioridad']);
            $table->dropColumn('prioridad');
        });

        Schema::table('turnos', function (Blueprint $table) {
            $table->enum('prioridad', ['normal', 'prioritaria'])->default('normal')->after('estado');
        });
    }
};
