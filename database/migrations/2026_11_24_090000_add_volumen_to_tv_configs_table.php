<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Volumen general del TV (0-100 %): el pito y la voz de los llamados. 100 = el volumen de siempre.
 * Con guarda: si la columna ya se creó a mano (phpMyAdmin), la migración no falla.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tv_configs', 'volumen')) {
            Schema::table('tv_configs', function (Blueprint $table) {
                $table->unsignedTinyInteger('volumen')->default(100)->after('ticker_enabled');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tv_configs', 'volumen')) {
            Schema::table('tv_configs', function (Blueprint $table) {
                $table->dropColumn('volumen');
            });
        }
    }
};
