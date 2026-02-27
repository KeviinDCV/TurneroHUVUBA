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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('auto_llamado_activo')->default(false)->after('inicio_canal_no_presencial')
                ->comment('Si está activo, el sistema llamará automáticamente un turno cuando el asesor lleva 10 min sin turno');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('auto_llamado_activo');
        });
    }
};
