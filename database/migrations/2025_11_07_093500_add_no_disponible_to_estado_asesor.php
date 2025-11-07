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
        // Modificar el ENUM para agregar 'no_disponible'
        DB::statement("ALTER TABLE users MODIFY COLUMN estado_asesor ENUM('disponible', 'ocupado', 'descanso', 'no_disponible') DEFAULT 'disponible'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver al ENUM original
        DB::statement("ALTER TABLE users MODIFY COLUMN estado_asesor ENUM('disponible', 'ocupado', 'descanso') DEFAULT 'disponible'");
    }
};
