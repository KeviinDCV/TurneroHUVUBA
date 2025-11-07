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
        // Verificar si la columna ya tiene el valor 'no_disponible'
        $result = DB::select("SHOW COLUMNS FROM users LIKE 'estado_asesor'");
        
        if (!empty($result)) {
            $type = $result[0]->Type;
            
            // Solo modificar si no incluye 'no_disponible'
            if (strpos($type, 'no_disponible') === false) {
                DB::statement("ALTER TABLE users MODIFY COLUMN estado_asesor ENUM('disponible', 'ocupado', 'descanso', 'no_disponible') DEFAULT 'disponible'");
            }
        }
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
