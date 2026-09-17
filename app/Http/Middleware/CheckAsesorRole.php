<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAsesorRole
{
    /**
     * Handle an incoming request.
     * Verificar que el usuario tenga rol de Asesor
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }
            return redirect()->route('admin.login')
                ->with('error', 'Debes iniciar sesión para acceder a esta página.');
        }

        $user = Auth::user();

        // Cuenta desactivada con la sesión abierta: se cierra aquí (desactivarUsuario ya borra sus sesiones; esto cubre el resto).
        if ($user->estaDesactivada()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Esta cuenta fue desactivada.'], 401);
            }
            return redirect()->route('admin.login')
                ->with('info', 'Esta cuenta fue desactivada. Si necesitas entrar, habla con un administrador.');
        }

        // Verificar que el usuario sea asesor
        if (!$user->esAsesor()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a esta función'
                ], 403);
            }
            
            // Si es administrador, redirigir a su dashboard
            if ($user->esAdministrador()) {
                return redirect()->route('admin.dashboard')
                    ->with('error', 'No tienes permisos para acceder al panel de asesor.');
            }

            // Para otros roles o casos no definidos, cerrar sesión por seguridad
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->with('error', 'No tienes permisos para acceder al panel de asesor.');
        }

        return $next($request);
    }
}
