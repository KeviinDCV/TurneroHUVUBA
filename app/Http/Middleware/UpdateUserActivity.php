<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserActivity
{
    /**
     * Handle an incoming request.
     * Actualiza la actividad del usuario autenticado
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo actualizar si el usuario está autenticado
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = session()->getId();



            // Verificar si la sesión actual coincide con la almacenada
            if ($user->session_id === $currentSessionId) {
                // Actualizar solo la actividad, mantener la misma sesión
                $user->update([
                    'last_activity' => now(),
                    'last_ip' => $request->ip()
                ]);
            } else {
                // Si no hay session_id almacenado, es un login reciente, actualizar la sesión
                if (empty($user->session_id)) {
                    $user->actualizarSession($currentSessionId, $request->ip());
                } else {
                    // Verificar si la sesión almacenada existe en la tabla sessions
                    $storedSessionExists = \DB::table('sessions')->where('id', $user->session_id)->exists();

                    if (!$storedSessionExists) {
                        $user->actualizarSession($currentSessionId, $request->ip());
                    } else {
                        // Si la sesión no coincide y hay una sesión almacenada válida, cerrar sesión por seguridad
                        \Log::warning('Sesión no coincide, cerrando por seguridad', [
                            'user_id' => $user->id,
                            'stored_session' => $user->session_id,
                            'current_session' => $currentSessionId,
                            'stored_session_exists' => $storedSessionExists
                        ]);

                        Auth::logout();
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();

                        return redirect()->route('admin.login')
                            ->with('info', 'Sesión cerrada por seguridad debido a actividad en otro dispositivo.');
                    }
                }
            }
        }

        return $response;
    }
}
