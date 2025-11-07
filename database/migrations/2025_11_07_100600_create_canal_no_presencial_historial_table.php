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
        if (!Schema::hasTable('canal_no_presencial_historial')) {
            Schema::create('canal_no_presencial_historial', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->text('actividad');
                $table->timestamp('inicio');
                $table->timestamp('fin')->nullable();
                $table->integer('duracion_minutos')->nullable(); // Duración en minutos
                $table->timestamps();
                
                // Índices para mejorar el rendimiento de consultas
                $table->index('user_id');
                $table->index('inicio');
                $table->index(['inicio', 'fin']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canal_no_presencial_historial');
    }
};
