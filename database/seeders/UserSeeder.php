<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador (solo si no existe)
        $adminUser = User::where('nombre_usuario', 'admin')->first();
        if (!$adminUser) {
            User::create([
                'nombre_completo' => 'Administrador HUV',
                'correo_electronico' => 'admin@huv.gov.co',
                'rol' => 'Administrador',
                'cedula' => '12345678',
                'nombre_usuario' => 'admin',
                'password' => Hash::make('admin123'),
                // Agregar valores por defecto para columnas que podrían ser requeridas
                'name' => 'Administrador HUV',
                'email' => 'admin@huv.gov.co',
            ]);
            $this->command->info('Usuario administrador creado: admin');
        } else {
            $this->command->info('Usuario administrador ya existe: admin');
        }

        // Crear usuario asesor (solo si no existe)
        $asesorUser = User::where('nombre_usuario', 'asesor')->first();
        if (!$asesorUser) {
            User::create([
                'nombre_completo' => 'Asesor de Prueba',
                'correo_electronico' => 'asesor@huv.gov.co',
                'rol' => 'Asesor',
                'cedula' => '87654321',
                'nombre_usuario' => 'asesor',
                'password' => Hash::make('asesor123'),
                // Agregar valores por defecto para columnas que podrían ser requeridas
                'name' => 'Asesor de Prueba',
                'email' => 'asesor@huv.gov.co',
            ]);
            $this->command->info('Usuario asesor creado: asesor');
        } else {
            $this->command->info('Usuario asesor ya existe: asesor');
        }
    }
}
