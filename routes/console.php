<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================
// LIMPIEZA AUTOMÁTICA DE TURNOS A MEDIANOCHE
// ============================================
// Este comando se ejecuta todos los días a las 12:00 AM (medianoche)
// Elimina los turnos del día anterior de la tabla 'turnos' (temporal)
// El historial en 'turno_historial' se mantiene intacto para reportes
Schedule::command('turnos:limpiar-antiguos --dias=0')
    ->dailyAt('00:00')
    ->timezone('America/Bogota')
    ->name('limpieza-automatica-turnos')
    ->onSuccess(function () {
        \Log::info('✅ Limpieza automática de turnos completada exitosamente');
    })
    ->onFailure(function () {
        \Log::error('❌ La limpieza automática de turnos falló');
    });
