<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForceLogoutSecurity
{
    /**
     * Handle an incoming request.
     * Fuerza el cierre de sesión solo en casos específicos de seguridad
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar si hay un parámetro específico de seguridad
        if ($request->has('force_logout') && Auth::check()) {
            Auth::logout();

            // Limpiar todas las variables de sesión
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirigir con mensaje informativo
            return redirect()->route('admin.login')
                ->with('info', 'Sesión cerrada por seguridad. Por favor, inicie sesión nuevamente.');
        }

        return $next($request);
    }
}
