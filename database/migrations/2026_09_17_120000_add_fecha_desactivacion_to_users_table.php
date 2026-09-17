<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Desactivar cuenta": con fecha, la cuenta no inicia sesión pero conserva sus turnos, su historial y su nombre en
 * Reportes y Gráficos (eliminarla los deja "sin asesor"). DATETIME y no TIMESTAMP: nunca se reescribe sola
 * (ver la migración de fecha_creacion).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'fecha_desactivacion')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dateTime('fecha_desactivacion')->nullable()->after('last_ip');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'fecha_desactivacion')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('fecha_desactivacion');
            });
        }
    }
};
