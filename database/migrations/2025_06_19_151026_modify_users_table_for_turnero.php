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
            // Hacer nullable las columnas originales que ya no usamos
            if (Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->change();
            }
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->change();
            }

            // Agregar nuevos campos para el sistema de turnero (solo si no existen)
            if (!Schema::hasColumn('users', 'nombre_completo')) {
                $table->string('nombre_completo')->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'correo_electronico')) {
                $table->string('correo_electronico')->nullable()->after('nombre_completo');
            }
            if (!Schema::hasColumn('users', 'rol')) {
                $table->enum('rol', ['Administrador', 'Asesor'])->default('Asesor')->after('correo_electronico');
            }
            if (!Schema::hasColumn('users', 'cedula')) {
                $table->string('cedula')->nullable()->after('rol');
            }
            if (!Schema::hasColumn('users', 'nombre_usuario')) {
                $table->string('nombre_usuario')->nullable()->after('cedula');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revertir cambios - eliminar columnas solo si existen
            $columnsToRemove = [];

            if (Schema::hasColumn('users', 'nombre_completo')) {
                $columnsToRemove[] = 'nombre_completo';
            }
            if (Schema::hasColumn('users', 'correo_electronico')) {
                $columnsToRemove[] = 'correo_electronico';
            }
            if (Schema::hasColumn('users', 'rol')) {
                $columnsToRemove[] = 'rol';
            }
            if (Schema::hasColumn('users', 'cedula')) {
                $columnsToRemove[] = 'cedula';
            }
            if (Schema::hasColumn('users', 'nombre_usuario')) {
                $columnsToRemove[] = 'nombre_usuario';
            }

            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }

            // Restaurar las columnas originales como NOT NULL
            if (Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable(false)->change();
            }
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable(false)->change();
            }
        });
    }
};
