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
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Nombre del servicio
            $table->string('descripcion')->nullable(); // Descripción opcional
            $table->enum('nivel', ['servicio', 'subservicio'])->default('servicio'); // Nivel: servicio o subservicio
            $table->unsignedBigInteger('servicio_padre_id')->nullable(); // ID del servicio padre (para subservicios)
            $table->enum('estado', ['activo', 'inactivo'])->default('activo'); // Estado del servicio
            $table->string('codigo')->unique()->nullable(); // Código único del servicio
            $table->integer('orden')->default(0); // Orden de visualización
            $table->boolean('ocultar_turno')->default(false); // Si está activo, los turnos no se muestran en TV ni se llaman
            $table->timestamps();

            // Índices y relaciones
            $table->foreign('servicio_padre_id')->references('id')->on('servicios')->onDelete('cascade');
            $table->index(['nivel', 'estado']);
            $table->index('servicio_padre_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
