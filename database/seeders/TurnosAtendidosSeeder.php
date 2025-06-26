<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Turno;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Caja;
use Carbon\Carbon;

class TurnosAtendidosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creando turnos atendidos de prueba...');

        // Limpiar turnos existentes del día
        Turno::whereDate('fecha_creacion', Carbon::today())->delete();

        // Obtener servicios activos
        $servicios = Servicio::where('estado', 'activo')->get();

        // Obtener asesores activos
        $asesores = User::where('rol', 'Asesor')->get();

        // Obtener cajas disponibles
        $cajas = Caja::all();

        if ($asesores->isEmpty()) {
            $this->command->warn('No hay asesores disponibles. Los turnos se crearán sin asesor asignado.');
        }

        foreach ($servicios as $servicio) {
            // Crear entre 5 y 50 turnos atendidos para cada servicio
            $cantidadTurnos = rand(5, 50);

            for ($i = 1; $i <= $cantidadTurnos; $i++) {
                $fechaCreacion = Carbon::today()->addHours(rand(8, 17))->addMinutes(rand(0, 59));
                $fechaLlamado = $fechaCreacion->copy()->addMinutes(rand(1, 30));
                $fechaAtencion = $fechaLlamado->copy()->addMinutes(rand(1, 15));

                // Asignar asesor y caja aleatoriamente (80% de probabilidad)
                $asesor = null;
                $caja = null;

                if (!$asesores->isEmpty() && rand(1, 10) <= 8) {
                    $asesor = $asesores->random();
                    if (!$cajas->isEmpty()) {
                        $caja = $cajas->random();
                    }
                }

                Turno::create([
                    'codigo' => $servicio->codigo,
                    'numero' => $i,
                    'servicio_id' => $servicio->id,
                    'asesor_id' => $asesor ? $asesor->id : null,
                    'caja_id' => $caja ? $caja->id : null,
                    'estado' => 'atendido',
                    'prioridad' => rand(1, 10) <= 2 ? 'prioritaria' : 'normal',
                    'fecha_creacion' => $fechaCreacion,
                    'fecha_llamado' => $fechaLlamado,
                    'fecha_atencion' => $fechaAtencion,
                    'duracion_atencion' => rand(60, 600) // Entre 1 y 10 minutos
                ]);
            }

            $this->command->info("Creados {$cantidadTurnos} turnos para: {$servicio->nombre}");
        }

        $totalTurnos = Turno::where('estado', 'atendido')->whereDate('fecha_creacion', Carbon::today())->count();
        $turnosConAsesor = Turno::where('estado', 'atendido')->whereDate('fecha_creacion', Carbon::today())->whereNotNull('asesor_id')->count();

        $this->command->info("Total de turnos atendidos hoy: {$totalTurnos}");
        $this->command->info("Turnos atendidos con asesor asignado: {$turnosConAsesor}");
    }
}
