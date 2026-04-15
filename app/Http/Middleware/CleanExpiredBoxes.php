<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Caja;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CleanExpiredBoxes
{
    /**
     * Handle an incoming request.
     * Limpia automáticamente las cajas y sesiones expiradas.
     * Throttled: solo ejecuta la limpieza cada 5 minutos para reducir carga de CPU.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo ejecutar la limpieza cada 5 minutos (300 segundos)
        // Cache::add() retorna true solo si la clave NO existía, evitando race conditions
        if (Cache::add('clean_expired_boxes_lock', true, 300)) {
            try {
                $this->cleanExpiredBoxes();
            } catch (\Exception $e) {
                // Si falla, liberar el lock para reintentar en la siguiente petición
                Cache::forget('clean_expired_boxes_lock');
            }
        }

        return $next($request);
    }

    /**
     * Ejecuta la limpieza real de sesiones huérfanas y cajas expiradas.
     */
    protected function cleanExpiredBoxes(): void
    {
        // Limpiar usuarios con sesiones que ya no existen en la tabla sessions
        $usersWithOrphanSessions = User::whereNotNull('session_id')
            ->get()
            ->filter(function ($user) {
                return !DB::table('sessions')->where('id', $user->session_id)->exists();
            });

        foreach ($usersWithOrphanSessions as $user) {
            $user->limpiarSession();
        }

        // Limpiar cajas asignadas a usuarios que están REALMENTE inactivos
        // Verificamos la última actividad del usuario, no la fecha de asignación de la caja
        $cajasAsignadas = Caja::whereNotNull('asesor_activo_id')
            ->whereNotNull('session_id')
            ->get();

        foreach ($cajasAsignadas as $caja) {
            $usuario = User::find($caja->asesor_activo_id);
            
            // Si el usuario no existe o su última actividad fue hace más de 30 minutos, liberar la caja
            if (!$usuario || ($usuario->last_activity && $usuario->last_activity->lt(now()->subMinutes(30)))) {
                $caja->update([
                    'asesor_activo_id' => null,
                    'session_id' => null,
                    'fecha_asignacion' => null,
                    'ip_asesor' => null
                ]);
                
                // También limpiar la sesión del usuario si existe
                if ($usuario) {
                    $usuario->limpiarSession();
                }
            }
        }
    }
}
