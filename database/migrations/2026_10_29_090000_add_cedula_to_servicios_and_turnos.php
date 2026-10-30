<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Atril usado por los pacientes: cada servicio puede pedir el número de cédula antes de imprimir el turno,
 * y el turno guarda la cédula que se escribió.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('servicios', 'requiere_cedula')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->boolean('requiere_cedula')->default(false)->after('requiere_priorizacion');
            });
        }

        if (!Schema::hasColumn('turnos', 'cedula')) {
            Schema::table('turnos', function (Blueprint $table) {
                $table->string('cedula', 20)->nullable()->after('prioridad');
                $table->index('cedula');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('turnos', 'cedula')) {
            Schema::table('turnos', function (Blueprint $table) {
                $table->dropIndex(['cedula']);
                $table->dropColumn('cedula');
            });
        }

        if (Schema::hasColumn('servicios', 'requiere_cedula')) {
            Schema::table('servicios', function (Blueprint $table) {
                $table->dropColumn('requiere_cedula');
            });
        }
    }
};
