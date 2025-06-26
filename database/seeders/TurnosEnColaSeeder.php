<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Turno;
use App\Models\Servicio;
use Carbon\Carbon;

class TurnosEnColaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creando turnos en cola de prueba...');

        // Obtener servicios activos
        $servicios = Servicio::where('estado', 'activo')->get();

        foreach ($servicios as $servicio) {
            // Crear entre 0 y 10 turnos pendientes para cada servicio
            $cantidadTurnos = rand(0, 10);
            
            if ($cantidadTurnos > 0) {
                // Obtener el último número de turno para este servicio hoy
                $ultimoNumero = Turno::where('servicio_id', $servicio->id)
                    ->whereDate('fecha_creacion', Carbon::today())
                    ->max('numero') ?? 0;
                
                for ($i = 1; $i <= $cantidadTurnos; $i++) {
                    $numeroTurno = $ultimoNumero + $i;
                    $fechaCreacion = Carbon::now()->subMinutes(rand(1, 120)); // Creados en las últimas 2 horas
                    
                    // 70% pendientes, 30% aplazados
                    $estado = rand(1, 10) <= 7 ? 'pendiente' : 'aplazado';
                    
                    Turno::create([
                        'codigo' => $servicio->codigo,
                        'numero' => $numeroTurno,
                        'servicio_id' => $servicio->id,
                        'estado' => $estado,
                        'prioridad' => rand(1, 10) <= 3 ? 'prioritaria' : 'normal',
                        'fecha_creacion' => $fechaCreacion,
                    ]);
                }
                
                $this->command->info("Creados {$cantidadTurnos} turnos en cola para: {$servicio->nombre}");
            } else {
                $this->command->info("Sin turnos en cola para: {$servicio->nombre}");
            }
        }

        $totalTurnosEnCola = Turno::whereIn('estado', ['pendiente', 'aplazado'])
            ->whereDate('fecha_creacion', Carbon::today())
            ->count();
            
        $this->command->info("Total de turnos en cola hoy: {$totalTurnosEnCola}");
    }
}
