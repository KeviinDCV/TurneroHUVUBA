<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     * Verificar que el usuario tenga rol de Administrador
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return redirect()->route('admin.login')
                ->with('error', 'Debes iniciar sesión para acceder a esta página.');
        }

        $user = Auth::user();

        // Verificar que el usuario sea administrador
        if (!$user->esAdministrador()) {
            // Si es asesor, redirigir a su dashboard
            if ($user->esAsesor()) {
                $cajaId = session('caja_seleccionada');
                if ($cajaId) {
                    return redirect()->route('asesor.dashboard')
                        ->with('error', 'No tienes permisos para acceder al panel administrativo.');
                } else {
                    return redirect()->route('asesor.seleccionar-caja')
                        ->with('error', 'No tienes permisos para acceder al panel administrativo.');
                }
            }

            // Para otros roles o casos no definidos, cerrar sesión por seguridad
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->with('error', 'No tienes permisos para acceder al panel administrativo.');
        }

        return $next($request);
    }
}
