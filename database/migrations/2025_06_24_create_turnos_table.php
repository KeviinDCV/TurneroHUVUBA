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
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo'); // Código del turno (ej: CIT-001)
            $table->integer('numero'); // Número del turno
            $table->unsignedBigInteger('servicio_id'); // Servicio al que pertenece
            $table->unsignedBigInteger('caja_id')->nullable(); // Caja que atiende el turno
            $table->unsignedBigInteger('asesor_id')->nullable(); // Asesor que atiende
            $table->enum('estado', ['pendiente', 'llamado', 'atendido', 'aplazado', 'cancelado'])->default('pendiente');
            $table->enum('prioridad', ['normal', 'prioritaria'])->default('normal');
            $table->timestamp('fecha_creacion');
            $table->timestamp('fecha_llamado')->nullable();
            $table->timestamp('fecha_atencion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices
            $table->index(['servicio_id', 'estado']);
            $table->index(['caja_id', 'estado']);
            $table->index(['asesor_id']);
            $table->index('fecha_creacion');
            
            // Claves foráneas
            $table->foreign('servicio_id')->references('id')->on('servicios')->onDelete('cascade');
            $table->foreign('caja_id')->references('id')->on('cajas')->onDelete('set null');
            $table->foreign('asesor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};
