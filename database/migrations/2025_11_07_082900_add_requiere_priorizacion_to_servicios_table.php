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
        Schema::table('servicios', function (Blueprint $table) {
            // Agregar campo para indicar si el servicio requiere selección de prioridad
            $table->boolean('requiere_priorizacion')->default(false)->after('ocultar_turno');
            
            // Agregar índice
            $table->index('requiere_priorizacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropIndex(['requiere_priorizacion']);
            $table->dropColumn('requiere_priorizacion');
        });
    }
};
