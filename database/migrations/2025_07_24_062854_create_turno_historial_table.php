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
        Schema::create('turno_historial', function (Blueprint $table) {
            $table->id();

            // Referencia al turno original (puede ser null si el turno original fue eliminado)
            $table->unsignedBigInteger('turno_original_id')->nullable();

            // Todos los campos del turno original
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
            $table->integer('duracion_atencion')->nullable();
            $table->text('observaciones')->nullable();

            // Campos adicionales para el historial
            $table->timestamp('fecha_backup')->useCurrent(); // Cuándo se creó este registro de backup
            $table->enum('tipo_backup', ['creacion', 'actualizacion', 'eliminacion'])->default('creacion'); // Tipo de evento que generó el backup
            $table->json('datos_adicionales')->nullable(); // Para almacenar información extra si es necesaria

            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['turno_original_id']);
            $table->index(['servicio_id', 'fecha_creacion']);
            $table->index(['caja_id', 'fecha_creacion']);
            $table->index(['asesor_id', 'fecha_creacion']);
            $table->index(['fecha_backup']);
            $table->index(['tipo_backup']);
            $table->index(['estado', 'fecha_creacion']);

            // Claves foráneas (con onDelete('set null') para mantener el historial aunque se eliminen los registros relacionados)
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
        Schema::dropIfExists('turno_historial');
    }
};
