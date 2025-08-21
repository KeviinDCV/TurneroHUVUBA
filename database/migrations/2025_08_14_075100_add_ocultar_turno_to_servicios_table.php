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
            // Verificar si la columna no existe antes de crearla
            if (!Schema::hasColumn('servicios', 'ocultar_turno')) {
                $table->boolean('ocultar_turno')->default(false)->after('orden')
                    ->comment('Si está activo, los turnos no se muestran en TV ni se llaman');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            // Verificar si la columna existe antes de eliminarla
            if (Schema::hasColumn('servicios', 'ocultar_turno')) {
                $table->dropColumn('ocultar_turno');
            }
        });
    }
};
