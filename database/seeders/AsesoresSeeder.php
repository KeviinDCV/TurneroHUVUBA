<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AsesoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creando asesores de prueba...');

        $asesores = [
            ['nombre_completo' => 'Karen Julieth Meneses', 'nombre_usuario' => 'kjmeneses', 'cedula' => '12345001'],
            ['nombre_completo' => 'Jorge Orlando Duarte Martinez', 'nombre_usuario' => 'jodumarti', 'cedula' => '12345002'],
            ['nombre_completo' => 'Luis Cruz', 'nombre_usuario' => 'lcruz', 'cedula' => '12345003'],
            ['nombre_completo' => 'Andrea Yulieth Rojas', 'nombre_usuario' => 'ayrojas', 'cedula' => '12345004'],
            ['nombre_completo' => 'Viviana Arango', 'nombre_usuario' => 'viarango', 'cedula' => '12345005'],
            ['nombre_completo' => 'Carlos Murillo', 'nombre_usuario' => 'carmurillo', 'cedula' => '12345006'],
            ['nombre_completo' => 'Juan David Delgado', 'nombre_usuario' => 'jddelgado', 'cedula' => '12345007'],
            ['nombre_completo' => 'Rosa Maria Prado', 'nombre_usuario' => 'rmprado', 'cedula' => '12345008'],
            ['nombre_completo' => 'Jesus Aldana', 'nombre_usuario' => 'jealdana', 'cedula' => '12345009'],
            ['nombre_completo' => 'Alejandra Gonzalez', 'nombre_usuario' => 'alejagonz', 'cedula' => '12345010'],
            ['nombre_completo' => 'Sandra Castro', 'nombre_usuario' => 'sandrac', 'cedula' => '12345011'],
            ['nombre_completo' => 'Maria Galvis', 'nombre_usuario' => 'magalvis', 'cedula' => '12345012'],
            ['nombre_completo' => 'Sofia Sanchez', 'nombre_usuario' => 'ssanchez', 'cedula' => '12345013'],
            ['nombre_completo' => 'Miguel Ojeda', 'nombre_usuario' => 'miojeda', 'cedula' => '12345014'],
            ['nombre_completo' => 'Diana Fernanda Velasco', 'nombre_usuario' => 'dfvelascov', 'cedula' => '12345015'],
        ];

        foreach ($asesores as $asesorData) {
            // Verificar si el usuario ya existe
            $existeUsuario = User::where('nombre_usuario', $asesorData['nombre_usuario'])
                                ->orWhere('cedula', $asesorData['cedula'])
                                ->first();

            if (!$existeUsuario) {
                User::create([
                    'nombre_completo' => $asesorData['nombre_completo'],
                    'cedula' => $asesorData['cedula'],
                    'correo_electronico' => $asesorData['nombre_usuario'] . '@huv.gov.co',
                    'nombre_usuario' => $asesorData['nombre_usuario'],
                    'rol' => 'Asesor',
                    'password' => Hash::make('password123'),
                    'estado_asesor' => 'disponible'
                ]);
                
                $this->command->info("Creado asesor: {$asesorData['nombre_completo']} ({$asesorData['nombre_usuario']})");
            } else {
                $this->command->warn("Asesor ya existe: {$asesorData['nombre_usuario']}");
            }
        }

        $totalAsesores = User::where('rol', 'Asesor')->count();
        $this->command->info("Total de asesores en el sistema: {$totalAsesores}");
    }
}
