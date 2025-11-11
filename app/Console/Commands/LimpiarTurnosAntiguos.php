<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Turno;
use Carbon\Carbon;

class LimpiarTurnosAntiguos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'turnos:limpiar-antiguos {--dias=0 : Número de días atrás para limpiar (0 = solo hoy)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia los turnos del día anterior de la tabla turnos (el historial se mantiene intacto)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dias = (int) $this->option('dias');
        
        $this->info('🔄 Iniciando limpieza automática de turnos...');
        $this->info('⏰ Hora de ejecución: ' . now()->format('Y-m-d H:i:s'));
        
        try {
            // Determinar qué fecha limpiar
            if ($dias === 0) {
                // Limpiar turnos del día actual (hasta el momento)
                $fecha = Carbon::today();
                $this->info("📅 Limpiando turnos del día: {$fecha->format('Y-m-d')}");
            } else {
                // Limpiar turnos de hace X días
                $fecha = Carbon::today()->subDays($dias);
                $this->info("📅 Limpiando turnos de hace {$dias} día(s): {$fecha->format('Y-m-d')}");
            }
            
            // Contar turnos antes de eliminar
            $turnosAEliminar = Turno::whereDate('fecha_creacion', $fecha)->get();
            $totalTurnos = $turnosAEliminar->count();
            
            if ($totalTurnos === 0) {
                $this->warn('⚠️  No hay turnos para limpiar en la fecha especificada.');
                return Command::SUCCESS;
            }
            
            // Mostrar desglose por estado
            $pendientes = $turnosAEliminar->where('estado', 'pendiente')->count();
            $llamados = $turnosAEliminar->where('estado', 'llamado')->count();
            $atendidos = $turnosAEliminar->where('estado', 'atendido')->count();
            $aplazados = $turnosAEliminar->where('estado', 'aplazado')->count();
            
            $this->info("\n📊 Resumen de turnos a eliminar:");
            $this->line("   • Pendientes: {$pendientes}");
            $this->line("   • Llamados: {$llamados}");
            $this->line("   • Atendidos: {$atendidos}");
            $this->line("   • Aplazados: {$aplazados}");
            $this->line("   • Total: {$totalTurnos}");
            
            // Eliminar turnos (el historial se mantiene automáticamente por el observer del modelo)
            $eliminados = Turno::whereDate('fecha_creacion', $fecha)->delete();
            
            // Verificar que el historial se mantiene
            $historialCount = \App\Models\TurnoHistorial::count();
            
            $this->info("\n✅ Limpieza completada exitosamente!");
            $this->line("   • Turnos eliminados: {$eliminados}");
            $this->line("   • Registros en historial: {$historialCount} (✓ preservados)");
            
            // Log de la operación
            \Log::info('Limpieza automática de turnos ejecutada', [
                'fecha_limpiada' => $fecha->format('Y-m-d'),
                'turnos_eliminados' => $eliminados,
                'desglose' => [
                    'pendientes' => $pendientes,
                    'llamados' => $llamados,
                    'atendidos' => $atendidos,
                    'aplazados' => $aplazados,
                ],
                'historial_preservado' => $historialCount,
                'hora_ejecucion' => now()->format('Y-m-d H:i:s')
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Error durante la limpieza: {$e->getMessage()}");
            
            \Log::error('Error en limpieza automática de turnos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
