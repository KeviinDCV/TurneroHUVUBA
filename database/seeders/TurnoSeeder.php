<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Turno;
use App\Models\Servicio;
use Carbon\Carbon;

class TurnoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener servicios existentes
        $servicios = Servicio::activos()->get();

        if ($servicios->isEmpty()) {
            $this->command->info('No hay servicios activos para crear turnos.');
            return;
        }

        $this->command->info('Creando turnos de prueba...');

        foreach ($servicios as $servicio) {
            // Crear algunos turnos pendientes para cada servicio
            for ($i = 1; $i <= 5; $i++) {
                Turno::create([
                    'codigo' => $servicio->codigo,
                    'numero' => $i,
                    'servicio_id' => $servicio->id,
                    'estado' => 'pendiente',
                    'prioridad' => $i <= 2 ? 'prioritaria' : 'normal',
                    'fecha_creacion' => Carbon::now(),
                ]);
            }

            // Crear algunos turnos aplazados
            for ($i = 6; $i <= 8; $i++) {
                Turno::create([
                    'codigo' => $servicio->codigo,
                    'numero' => $i,
                    'servicio_id' => $servicio->id,
                    'estado' => 'aplazado',
                    'prioridad' => 'normal',
                    'fecha_creacion' => Carbon::now(),
                ]);
            }

            $this->command->info("Creados turnos para servicio: {$servicio->nombre}");
        }

        $this->command->info('Turnos de prueba creados exitosamente.');
    }
}
