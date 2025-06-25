<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Caja;
use Illuminate\Support\Facades\DB;

class CleanExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:clean-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia sesiones expiradas (más de 15 minutos) y libera recursos asociados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando limpieza de sesiones expiradas...');

        // Obtener todos los usuarios con session_id
        $usersWithSessions = User::whereNotNull('session_id')->get();
        $cleanedUsers = 0;
        $cleanedBoxes = 0;

        foreach ($usersWithSessions as $user) {
            $shouldClean = false;
            $reason = '';

            // Verificar si la sesión ha expirado por tiempo (más de 15 minutos)
            if ($user->last_activity && $user->last_activity->diffInMinutes(now()) >= 15) {
                $shouldClean = true;
                $reason = 'sesión expirada por tiempo';
            }
            // Verificar si la sesión no existe en la tabla sessions
            elseif (!DB::table('sessions')->where('id', $user->session_id)->exists()) {
                $shouldClean = true;
                $reason = 'sesión no existe en base de datos';
            }

            if ($shouldClean) {
                // Limpiar sesión del usuario
                $user->limpiarSession();
                $cleanedUsers++;

                // Liberar cualquier caja que el usuario tenga asignada
                $cajasLiberadas = Caja::where('asesor_activo_id', $user->id)->count();
                if ($cajasLiberadas > 0) {
                    Caja::where('asesor_activo_id', $user->id)->update([
                        'asesor_activo_id' => null,
                        'session_id' => null,
                        'fecha_asignacion' => null,
                        'ip_asesor' => null
                    ]);
                    $cleanedBoxes += $cajasLiberadas;
                }

                $this->line("Usuario {$user->nombre_usuario} limpiado ({$reason})");
            }
        }

        // Limpiar sesiones huérfanas en la tabla sessions (sin usuario asociado o expiradas)
        $expiredSessionsCount = DB::table('sessions')
            ->where('last_activity', '<', now()->subMinutes(15)->timestamp)
            ->count();

        if ($expiredSessionsCount > 0) {
            DB::table('sessions')
                ->where('last_activity', '<', now()->subMinutes(15)->timestamp)
                ->delete();
            $this->line("Eliminadas {$expiredSessionsCount} sesiones expiradas de la tabla sessions");
        }

        $this->info("Limpieza completada:");
        $this->info("- Usuarios limpiados: {$cleanedUsers}");
        $this->info("- Cajas liberadas: {$cleanedBoxes}");
        $this->info("- Sesiones expiradas eliminadas: {$expiredSessionsCount}");

        return Command::SUCCESS;
    }
}
