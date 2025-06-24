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
        Schema::table('cajas', function (Blueprint $table) {
            $table->unsignedBigInteger('asesor_activo_id')->nullable()->after('estado');
            $table->string('session_id')->nullable()->after('asesor_activo_id');
            $table->timestamp('fecha_asignacion')->nullable()->after('session_id');
            $table->ipAddress('ip_asesor')->nullable()->after('fecha_asignacion');

            // Clave foránea
            $table->foreign('asesor_activo_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->dropForeign(['asesor_activo_id']);
            $table->dropColumn(['asesor_activo_id', 'session_id', 'fecha_asignacion', 'ip_asesor']);
        });
    }
};
