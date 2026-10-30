<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base de pacientes del atril: con qué documentos se da turno en los servicios que piden cédula y qué nombre se muestra.
 * Solo documento y nombre: del archivo de citas no se guarda nada más (ni teléfonos, ni EPS, ni observaciones).
 * pacientes_cargas deja constancia de quién cargó qué archivo y cuándo.
 * pacientes_nuevos es la tabla de paso: una carga se escribe ahí y solo al terminar pasa a ser la base (PacienteController).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pacientes_cargas')) {
            Schema::create('pacientes_cargas', function (Blueprint $table) {
                $table->id();
                $table->string('archivo', 190)->nullable();
                $table->unsignedInteger('filas')->default(0);    // filas que traía el archivo
                $table->unsignedInteger('total')->default(0);    // pacientes que quedaron
                $table->string('estado', 12)->default('en_curso'); // en_curso | lista | eliminada
                $table->string('cargado_por', 150)->nullable();
                $table->timestamps();
            });
        }

        foreach (['pacientes', 'pacientes_nuevos'] as $tabla) {
            if (!Schema::hasTable($tabla)) {
                Schema::create($tabla, function (Blueprint $table) {
                    $table->string('documento', 20)->primary();
                    $table->string('nombre', 150);
                    $table->unsignedBigInteger('carga_id')->index();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes_nuevos');
        Schema::dropIfExists('pacientes');
        Schema::dropIfExists('pacientes_cargas');
    }
};
