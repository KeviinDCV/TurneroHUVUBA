<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Servicio;

class UserServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el usuario asesor de prueba
        $asesor = User::where('rol', 'Asesor')->first();
        
        if (!$asesor) {
            $this->command->error('No se encontró ningún usuario con rol Asesor');
            return;
        }

        // Obtener algunos servicios para asignar
        $servicios = Servicio::where('estado', 'activo')
                            ->whereIn('codigo', ['CIT', 'COP', 'FAC'])
                            ->get();

        if ($servicios->isEmpty()) {
            $this->command->error('No se encontraron servicios para asignar');
            return;
        }

        // Asignar servicios al asesor
        foreach ($servicios as $servicio) {
            if (!$asesor->tieneServicio($servicio->id)) {
                $asesor->servicios()->attach($servicio->id);
                $this->command->info("Servicio '{$servicio->nombre}' asignado al asesor '{$asesor->nombre_completo}'");
            } else {
                $this->command->info("Servicio '{$servicio->nombre}' ya estaba asignado al asesor");
            }
        }

        $this->command->info('Asignación de servicios completada');
    }
}
