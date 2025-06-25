<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Caja;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CleanExpiredBoxes
{
    /**
     * Handle an incoming request.
     * Limpia automáticamente las cajas y sesiones expiradas
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
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

        // Limpiar cajas con sesiones expiradas (más de 15 minutos de inactividad)
        Caja::whereNotNull('asesor_activo_id')
            ->whereNotNull('session_id')
            ->where('fecha_asignacion', '<', now()->subMinutes(15))
            ->update([
                'asesor_activo_id' => null,
                'session_id' => null,
                'fecha_asignacion' => null,
                'ip_asesor' => null
            ]);

        return $next($request);
    }
}
