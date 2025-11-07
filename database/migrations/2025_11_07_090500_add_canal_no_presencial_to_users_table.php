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
            // Campo para guardar la actividad en canal no presencial
            $table->text('actividad_canal_no_presencial')->nullable()->after('estado_asesor');
            // Campo para registrar cuándo comenzó la actividad en canal no presencial
            $table->timestamp('inicio_canal_no_presencial')->nullable()->after('actividad_canal_no_presencial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('actividad_canal_no_presencial');
            $table->dropColumn('inicio_canal_no_presencial');
        });
    }
};
